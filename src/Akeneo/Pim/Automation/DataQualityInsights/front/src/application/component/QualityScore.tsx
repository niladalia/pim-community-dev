import React, { FC, HTMLAttributes } from 'react';
import styled, { css } from 'styled-components';
import { AkeneoThemedProps, getColor, Override } from 'akeneo-design-system';

type Props = Override<
  HTMLAttributes<HTMLDivElement>,
  {
    score: string | null;
    size?: 'normal' | 'big';
    stacked?: boolean;
    rounded?: Rounded;
  }
>;

type Rounded = 'all' | 'left' | 'right' | 'none';

/**
 * <QualityScore score={null} />
 *
 * <QualityScore score={'N/A'} />
 *
 * <QualityScore score={'A'} />
 *
 * <QualityScore score={'A'} size={'big'} />
 *
 * <QualityScore score={'A'} size={'big'} rounded={'left} stacked />
 *
 */
const QualityScore: FC<Props> = ({ score, size = 'normal', rounded = 'all', stacked = false, ...props }) => {
  if (score === 'N/A' || score === null) {
    return <>N/A</>;
  }

  return stacked ? (
    <Wrapper size={size}>
      <>
        <QualityScoreEmptyContainer score={score} size={size} top={-2} left={4} />
        <QualityScoreEmptyContainer score={score} size={size} top={0} left={2} />
      </>
      <Container score={score} size={size} rounded={rounded} stacked={stacked} {...props}>
        {score}
      </Container>
    </Wrapper>
  ) : (
    <Container score={score} size={size} rounded={rounded} {...props}>
      {score}
    </Container>
  );
};

const getContainerBorderRadius = (rounded: Rounded) => {
  switch (rounded) {
    case 'all':
      return '4px';
    case 'left':
      return '4px 0 0 4px';
    case 'right':
      return '0 4px 4px 0';
    case 'none':
      return '0';
  }
};

const containerStackedStyled = css<{ score: string; size: string }>`
  position: absolute;
  left: 2px;
  border-radius: ${getContainerBorderRadius('all')};

  ${({ score, size }) =>
    size === 'big' &&
    css`
      border: 1px solid ${switchContainer(score)};
      top: 2px;
    `};
`;

const Container = styled.div<{ score: string; size: string; rounded: Rounded; stacked?: boolean }>`
  text-align: center;
  display: inline-block;
  text-transform: uppercase;
  font-weight: bold;
  width: 20px;
  height: 20px;
  font-size: 13px;
  border-radius: ${({ rounded }) => getContainerBorderRadius(rounded)};

  ${({ size }) =>
    size === 'big' &&
    css`
      width: 25px;
      height: 25px;
      font-size: 15px;
      line-height: 25px;
      top: -2px;
      position: relative;
      margin: 0 -2px 0 -2px;
      border-radius: ${getContainerBorderRadius('all')};
    `};

  ${({ size, stacked, score }) =>
    size === 'normal' && stacked &&
    css`
    top: 2px;
    left: 0px !important;
    border: 1px solid  ${switchContainer(score)};
    `};

  ${({ score }) => score === 'A' && AScore}
  ${({ score }) => score === 'B' && BScore}
  ${({ score }) => score === 'C' && CScore}
  ${({ score }) => score === 'D' && DScore}
  ${({ score }) => score === 'E' && EScore}

  ${({ stacked }) => stacked && containerStackedStyled}
`;
Container.defaultProps = {
  stacked: false,
  rounded: 'all',
};

const Wrapper = styled.div<{ size: string }>`
  position: relative;
  width: ${({ size }) => (size === 'big' ? '25px' : '20px')};
  height: ${({ size }) => (size === 'big' ? '25px' : '20px')};
  margin: -2px 2px 0 -2px;
`;

const switchContainer = (score: string) => {
  switch (score) {
    case 'A': {
      return ABorderScore;
    }
    case 'B': {
      return BBorderScore;
    }
    case 'C': {
      return CBorderScore;
    }
    case 'D': {
      return DBorderScore;
    }
    case 'E': {
      return EBorderScore;
    }
    default:
      return 'black';
  }
};

const QualityScoreEmptyContainer = styled.div<
  { score: string; size: string; top: number; left: number } & AkeneoThemedProps
  >`
  top: ${({ top }) => top}px;
  left: ${({ left }) => left}px;
  position: absolute;
  display: inline-block;
  width: ${({ size }) => (size === 'big' ? '25px' : '20px')};
  height: ${({ size }) => (size === 'big' ? '25px' : '20px')};
  border-radius: 4px !important;
  border: 1px solid ${({ score }) => switchContainer(score)};
  background: ${getColor('white')};
`;

const ABorderScore = css`
  ${({ theme }) => theme.color.green60};
`;
const BBorderScore = css`
  ${({ theme }) => theme.color.green100};
`;
const CBorderScore = css`
  ${({ theme }) => theme.color.yellow60};
`;
const DBorderScore = css`
  ${({ theme }) => theme.color.red40};
`;
const EBorderScore = css`
  ${({ theme }) => theme.color.red100};
`;

const AScore = css`
  background: ${({ theme }) => theme.color.green20};
  color: ${({ theme }) => theme.color.green120};
`;
const BScore = css`
  background: ${({ theme }) => theme.color.green60};
  color: ${({ theme }) => theme.color.green140};
`;
const CScore = css`
  background: ${({ theme }) => theme.color.yellow20};
  color: ${({ theme }) => theme.color.yellow120};
`;
const DScore = css`
  background: ${({ theme }) => theme.color.red20};
  color: ${({ theme }) => theme.color.red100};
`;
const EScore = css`
  background: ${({ theme }) => theme.color.red60};
  color: ${({ theme }) => theme.color.red140};
`;

export { QualityScore };
